FROM nginx:latest

WORKDIR /usr/src/app

COPY . .

COPY nginx.conf /etc/nginx/nginx.conf
COPY sites-enabled/ /etc/nginx/sites-enabled/

EXPOSE 80

ENTRYPOINT [ "nginx", "-g", "daemon off;" ]
