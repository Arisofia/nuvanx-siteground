cd /home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html
echo 'PWD: ' $(pwd)
echo 'Version: ' $(wp core version)
echo 'SiteUrl: ' $(wp option get siteurl)
echo 'Home: ' $(wp option get home)
echo 'BlogPublic: ' $(wp option get blog_public)
echo 'DB_NAME: ' $(wp config get DB_NAME)
echo 'DB_USER: ' $(wp config get DB_USER)
echo 'table_prefix: ' $(wp config get table_prefix)
