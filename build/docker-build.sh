#!/bin/bash

# Ensure there is a version
if [ -z $VERSION ]; then
  VERSION=7.$(date +"%Y-%m-%d")
fi

# Setup environment
mkdir -p $(dirname $0)/../release/tmp
workingDir=$(realpath "$(dirname $0)/../release/tmp")
cd $workingDir

# Ensure buildfiles does exist
if [ ! -d $workingDir/buildfiles ]; then
  git clone --depth 1 https://github.com/akeeba/buildfiles.git

  composer install -d $workingDir/buildfiles
fi

# Copy current code to tmp directory
rsync -av $(dirname $0)/../ $workingDir/release-system --exclude tmp --exclude .git

# Download phing if it doesn't exist
if [ ! -f $workingDir/phing ]; then
  curl https://www.phing.info/get/phing-latest.phar --output $workingDir/phing
fi

# Build it
cd $workingDir/release-system/build
rm -rf $workingDir/release-system/release

# Build it with version 7 and date, can be
php $workingDir/phing git -Dversion=$VERSION

# Copy release back from tmp folder
cp $workingDir/release-system/release/*.zip $workingDir/..
cd $workingDir

# Delete copy
rm -rf $workingDir/release-system
