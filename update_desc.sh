#!/usr/bin/bash

filename=$1
repo_dir=/var/www/html/english.libmuy.com/app-gh-page/desc
cd $repo_dir && \
git pull && \
git add $filename && \
git commit -m "add $filename" && \
git push origin master
