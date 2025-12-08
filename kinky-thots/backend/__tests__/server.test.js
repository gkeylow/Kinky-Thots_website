const request = require('supertest');
const path = require('path');

// Mock mariadb and fs before requiring the server module
jest.mock('mariadb', () => {
  const getConnection = jest.fn();
  return {
    createPool: jest.fn(() => ({ getConnection }))
  };
});

jest.mock('fs', () => ({
  existsSync: jest.fn(() => true),
  mkdirSync: jest.fn(),
  unlinkSync: jest.fn()
}));

jest.mock('express-fileupload', () => () => (req, res, next) => next());

// Because server.js starts listening immediately, we need to require it in a way
// that exports the app to test. We'll wrap require in a function that loads and
// returns the app instance from server.js after injecting a small change.
let app;

beforeAll(() => {
  // Use jest.isolateModules to avoid module cache pollution across tests
  jest.isolateModules(() => {
    // Patch server.js to export app by monkey-patching listen to no-op and capturing app
    const http = require('http');
    jest.spyOn(http.Server.prototype, 'listen').mockImplementation(function(){ return this; });
    app = require('../server');
  });
});

afterAll(() => {
  jest.resetModules();
  jest.clearAllMocks();
});

// Helpers to control DB behavior per test
const mariadb = require('mariadb');

function mockDbConnection(methods) {
  mariadb.createPool().getConnection.mockResolvedValue({
    query: jest.fn(async (...args) => {
      const sql = args[0];
      if (methods && methods.query) {
        return methods.query(sql, args[1]);
      }
      return [];
    }),
    release: jest.fn()
  });
}

describe('Health endpoint', () => {
  test('Should return OK status with ISO timestamp', async () => {
    const res = await request(app).get('/health');
    expect(res.status).toBe(200);
    expect(res.body.status).toBe('OK');
    expect(() => new Date(res.body.timestamp).toISOString()).not.toThrow();
  });
});

describe('Gallery endpoint', () => {
  test('Should return list of images ordered by upload_time', async () => {
    mockDbConnection({
      query: (sql) => {
        if (/SELECT id, filename, upload_time FROM images/i.test(sql)) {
          return [
            { id: 2, filename: 'b.png', upload_time: '2024-05-02' },
            { id: 1, filename: 'a.jpg', upload_time: '2024-05-01' }
          ];
        }
        return [];
      }
    });

    const res = await request(app).get('/gallery');
    expect(res.status).toBe(200);
    expect(res.body).toEqual([
      { id: 2, filename: 'b.png', upload_time: '2024-05-02' },
      { id: 1, filename: 'a.jpg', upload_time: '2024-05-01' }
    ]);
  });

  test('Should return 500 on database error', async () => {
    mariadb.createPool().getConnection.mockRejectedValue(new Error('db down'));
    const res = await request(app).get('/gallery');
    expect(res.status).toBe(500);
    expect(res.body).toEqual({ error: 'Database error' });
  });
});

describe('Delete endpoint', () => {
  test('Should 404 when image id does not exist', async () => {
    mockDbConnection({
      query: (sql) => {
        if (/SELECT filename FROM images/i.test(sql)) {
          return [];
        }
        return [];
      }
    });

    const res = await request(app).delete('/delete/123');
    expect(res.status).toBe(404);
    expect(res.body).toEqual({ error: 'Image not found' });
  });

  test('Should delete database record and file when exists', async () => {
    const fs = require('fs');
    fs.existsSync.mockReturnValue(true);

    mockDbConnection({
      query: (sql) => {
        if (/SELECT filename FROM images/i.test(sql)) {
          return [{ filename: 'some.png' }];
        }
        if (/DELETE FROM images/i.test(sql)) {
          return { affectedRows: 1 };
        }
        return [];
      }
    });

    const res = await request(app).delete('/delete/42');
    expect(res.status).toBe(200);
    expect(res.body).toEqual({ success: true });
    expect(fs.unlinkSync).toHaveBeenCalledWith(path.join(__dirname, '..', 'uploads', 'some.png'));
  });

  test('Should return 500 on delete error', async () => {
    mariadb.createPool().getConnection.mockRejectedValue(new Error('db err'));
    const res = await request(app).delete('/delete/1');
    expect(res.status).toBe(500);
    expect(res.body).toEqual({ error: 'Delete failed' });
  });
});
