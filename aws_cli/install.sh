#!/bin/bash

curl "https://s3.amazonaws.com/aws-cli/awscli-bundle.zip" -o "awscli-bundle.zip";
unzip awscli-bundle.zip;
./awscli-bundle/install -b ~/.local/bin/aws;

rm awscli-bundle.zip;
rm -rf awscli-bundle;

