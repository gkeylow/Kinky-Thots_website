  <h1 align="center">Hi 👋, I'm gkeylow</h1>
  <h3 align="center">A hobbyist coder with a background in electronics</h3>

  - 🔭 I’m currently working on [Kinky-Thots Website](https://github.com/Kinky-Thots/Kinky_Thots_Official_Website)

  - 🌱 I’m currently learning **Backend / Frontend / Javascripts**

  <h3 align="left">Connect with me:</h3>
    <p align="left">
      <a href="https://twitter.com/kinkythotsmodel" target="blank"><img align="center" src="https://raw.githubusercontent.com/rahuldkjain/github-profile-readme-generator/master/src/images/icons/Social/twitter.svg" alt="kinkythotsmodel" height="30" width="40" /></a>
      <a href="https://fb.com/https://www.facebook.com/kinkythots" target="blank"><img align="center" src="https://raw.githubusercontent.com/rahuldkjain/github-profile-readme-generator/master/src/images/icons/Social/facebook.svg" alt="https://www.facebook.com/kinkythots" height="30" width="40" /></a>
    </p>

  <h3 align="left">Languages and Tools:</h3>
    <p align="left"><a href="https://aws.amazon.com" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/amazonwebservices/amazonwebservices-original-wordmark.svg" alt="aws" width="40" height="40"/></a> 
      <a href="https://www.gnu.org/software/bash/" target="_blank" rel="noreferrer"><img src="https://www.vectorlogo.zone/logos/gnu_bash/gnu_bash-icon.svg" alt="bash" width="40" height="40"/> </a> <a href="https://www.w3schools.com/css/" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/css3/css3-original-wordmark.svg" alt="css3" width="40" height="40"/></a> 
      <a href="https://www.docker.com/" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/docker/docker-original-wordmark.svg" alt="docker" width="40" height="40"/> </a> <a href="https://expressjs.com" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/express/express-original-wordmark.svg" alt="express" width="40" height="40"/></a>
      <a href="https://git-scm.com/" target="_blank" rel="noreferrer"><img src="https://www.vectorlogo.zone/logos/git-scm/git-scm-icon.svg" alt="git" width="40" height="40"/> </a> <a href="https://www.w3.org/html/" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/html5/html5-original-wordmark.svg" alt="html5" width="40" height="40"/></a>
      <a href="https://www.linux.org/" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/linux/linux-original.svg" alt="linux" width="40" height="40"/> </a> <a href="https://mariadb.org/" target="_blank" rel="noreferrer"><img src="https://www.vectorlogo.zone/logos/mariadb/mariadb-icon.svg" alt="mariadb" width="40" height="40"/></a>
      <a href="https://www.mongodb.com/" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/mongodb/mongodb-original-wordmark.svg" alt="mongodb" width="40" height="40"/></a>
      <a href="https://www.mysql.com/" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/mysql/mysql-original-wordmark.svg" alt="mysql" width="40" height="40"/></a>
      <a href="https://www.nginx.com" target="_blank" rel="noreferrer"><img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/nginx/nginx-original.svg" alt="nginx" width="40" height="40"/></a>
      <a href="https://nodejs.org" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/nodejs/nodejs-original-wordmark.svg" alt="nodejs" width="40" height="40"/></a>
      <a href="https://www.photoshop.com/en" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/photoshop/photoshop-line.svg" alt="photoshop" width="40" height="40"/></a> 
      <a href="https://reactjs.org/" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/devicons/devicon/master/icons/react/react-original-wordmark.svg" alt="react" width="40" height="40"/></a> 
      <a href="https://www.selenium.dev" target="_blank" rel="noreferrer"> <img src="https://raw.githubusercontent.com/detain/svg-logos/780f25886640cef088af994181646db2f6b1a3f8/svg/selenium-logo.svg" alt="selenium" width="40" height="40"/></a> 
      <a href="https://www.sqlite.org/" target="_blank" rel="noreferrer"> <img src="https://www.vectorlogo.zone/logos/sqlite/sqlite-icon.svg" alt="sqlite" width="40" height="40"/></a> 
    </p>

## Tech Stack
- **Web Server**: Apache2 with PHP (Docker: kinky-web)
- **Backend**: Node.js WebSocket chat server on port 3001 (Docker: kinky-backend)
- **Database**: MariaDB 10.11 (Docker: kinky-db)
- **Streaming**: nginx-rtmp (Docker: kinky-rtmp) - RTMP on :1935, auto HLS conversion
- **CDN**: Pushr CDN with S3-compatible storage (Sonic)
- **Build Tools**: Vite, Tailwind CSS, ESLint, Prettier
- **SSL**: Certbot available in Docker for future native HTTPS (currently using localtonet)

"kinky-thots/
├── src/              # Source files (development)
│   ├── js/           # JavaScript modules (ES6+)
│   │   ├── main.js   # Navigation/dropdown handler
│   │   ├── index.js  # Homepage (imports landing.css)
│   │   ├── live.js   # Live streaming module
│   │   ├── gallery.js # Photo gallery (imports media-gallery.css)
│   │   ├── porn.js   # Video gallery (imports media-gallery.css)
│   │   ├── sissylonglegs.js # Model page (imports landing.css)
│   │   └── content.js # Content pages (imports content.css)
│   └── css/          # Modular CSS (Dec 2024 refactor)
│       ├── main.css  # Tailwind entry + imports layout.css
│       ├── layout.css # Header (nav) + Footer - ALL pages
│       ├── landing.css # Hero, About, Skills, Portfolio, Contact
│       ├── media-gallery.css # Photo/video grid, upload, lightbox
│       ├── content.css # Text pages (terms, etc.)
│       ├── live.css  # Live streaming + chat
│       └── chat.css  # Chat component styles
├── assets/           # Public assets
│   ├── dist/         # Built JS/CSS (from Vite)
│   ├── thumbnails/   # Video thumbnails
│   └── *.css/*.js    # Legacy assets (being migrated)
├── backend/          # Node.js backend (protected)
├── config/           # Configuration files (protected)
├── scripts/          # Shell scripts (protected)
│   ├── rtmp-to-hls.sh
│   ├── stream-watcher.sh
│   └── *.sh
├── hls/              # HLS stream output (public)
├── uploads/          # User uploads (protected)
├── logs/             # Application logs (protected)
├── data/             # Data files (protected)
├── backups/          # Backups (protected)
└── docs/             # Documentation (protected)"
