#!/bin/sh

VERSION=`git describe --abbrev=0 --tags`

APP="client/app/js/combined-js-app-$VERSION.js"

APPTAG="<script src='js/combined-js-app-$VERSION.js'></script>"

SSHTARGET="$1"
VERSIONDIR="$2/$VERSION"

mkdir temp

# Prep the server
php server/artisan cache:clear

# Remove unneccesary client bits.
mv client client-temp

mkdir client
cp -r client-temp/app client

# Combine javascripts and minify
awk 'FNR==1{print ""}1' client/app/js/angular.min.js \
    client/app/js/angular-route.min.js \
    client/app/js/ui-bootstrap-custom-tpls-0.10.0.min.js\
    client/app/js/app.js \
    client/app/js/controllers.js > temp/app.temp.js
java -jar ~/yuicompressor-2.4.8.jar temp/app.temp.js -o $APP

# Switch to concatenated minified JS includes.
mv client/app/index.html temp/index.orig.html
sed '/<!--DEV-->/d' temp/index.orig.html > temp/index.temp.html
sed "s%.*DEPLOY SCRIPT TARGET.*%$APPTAG%g" temp/index.temp.html > client/app/index.html

# Don't upload vendor directory.
mv server/vendor .

# Upload to target in a versioned directory
ssh $SSHTARGET ". ~/.bash_profile;\
                rm -rf $VERSIONDIR;\
                mkdir $VERSIONDIR"
scp -r server $SSHTARGET:$VERSIONDIR/
scp -r client $SSHTARGET:$VERSIONDIR/
ssh $SSHTARGET ". ~/.bash_profile;\
                composer install -d $VERSIONDIR/server;"
ssh -t $SSHTARGET "sudo chown -R daemon:daemon $VERSIONDIR/server/app/storage"
ssh $SSHTARGET "cp $2/conf/.env.staging.php $VERSIONDIR/server;\
                unlink /apps/bcbento;\
                ln -s $VERSIONDIR /apps/bcbento"

# Undo changes
mv temp/index.orig.html client/app/index.html
rm $APP
rm -r temp
rm -r client
mv ./vendor server/
mv client-temp client
