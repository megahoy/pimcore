# pimcore [![Build Status](https://travis-ci.org/megahoy/pimcore.svg?branch=master)](https://travis-ci.org/megahoy/pimcore)
Pimcore test project. <br /><br />
Create database for pimcore: <br />
`mysql -u root -p -D information_schema -e "CREATE DATABASE pimcore charset=utf8;"`

Execute pimcore.sql: <br />
`mysql -u root -p pimcore < pimcore.sql`

Setup writing permissions for folder: <br />
`sudo chmod -R 777 /path_to_pimcore/website/var`
