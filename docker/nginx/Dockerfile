FROM nginx:alpine

RUN ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log && \
    rm -rf /etc/nginx/conf.d/*

COPY ./default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
