cd 

cd cydynni
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "cydynni:"$branch":"$commit
cd

cd demandshaper
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "demandshaper:"$branch":"$commit
cd

cd /var/www/emoncms
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "emoncms:"$branch":"$commit
cd

cd /var/www/emoncms/Modules/device
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "emoncms-mod-device:"$branch":"$commit
cd


