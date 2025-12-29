FROM tiangolo/nginx-rtmp:latest

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Create HLS output directory
RUN mkdir -p /tmp/hls && chmod 777 /tmp/hls

EXPOSE 1935

CMD ["nginx", "-g", "daemon off;"]
