# lead-aggregator

Lead aggregator app.

###CREATE PROD BUILD
```
$ npm run build
$ git add --all ./build
$ git commit -m "Created new build"
$ git push -u
```

###Deploy to Server
```

1. Connect to Server
$ ssh -i ./your_key_name username@server_ip_address
$ cd /var/www/html/lead-aggregator
$ git pull

## RUN command below on server only if not build added to master

$ cd frontend && npm install && npm run build
```
