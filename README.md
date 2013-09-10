#LinodeDDNS

DDNS Script using Linode's DNS API

##Usage

Setup DD-WRT with like so

    DDNS Service: Custom
    DYNDNS Server: ddns.[Your Sername Here]
    User Name: [Your user name]
    Password: [Your password]
    Host Name: [Your subdomain]
    URL: http://ddns.[Your domain name]/

So, for example, to create/update home.mydomain.com to point to your router's public IP, it would post to ddns.mydomain.com/home.