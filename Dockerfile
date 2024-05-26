FROM nginx:latest

WORKDIR /usr/src/app

COPY . .

COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/fastcgi.conf /etc/nginx/fastcgi.conf
COPY nginx/sites-enabled/ /etc/nginx/sites-enabled/

EXPOSE 80

ENTRYPOINT [ "nginx", "-g", "daemon off;" ]
