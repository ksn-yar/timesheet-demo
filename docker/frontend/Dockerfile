FROM node:22-alpine

RUN apk add --no-cache bash \
    && npm install -g npm@11

WORKDIR /var/www/frontend

RUN chown node:node /var/www/frontend

USER node

EXPOSE 3000

CMD ["sh", "-c", "npm install && npm run dev"]
