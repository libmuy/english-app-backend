#!/usr/bin/bash

filename=$1
repo_dir=/var/www/html/english.libmuy.com/app-gh-page
cd $repo_dir && \
./commit.sh 'add $filename'