#LinodeDDNS

DDNS Script using Linode's DNS API

##Usage

Configure your router to ping http://[USERNAME]:[PASSWORD]@ddns.[DOMAIN]/[NAME], where:

- USERNAME is your DDNS_USERNAME value,
- PASSWORD is your DDNS_PASSWORD value,
- DOMAIN is your domain name the script can be accessed at and where the subdomain will go
- NAME is the name of the subdomain you wish to use for DDNS

Example:

http://root:root@ddns.mydomain.com/home

Will add an A record for home to mydomain.com, pointing to the IP it's coming from.