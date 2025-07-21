#\!/bin/bash
ssh root@137.184.113.161 << 'ENDSSH'
cd /var/www/listerino
echo "Current git remotes:"
git remote -v
echo -e "\nGit status:"
git status --short
echo -e "\nCurrent branch:"
git branch
echo -e "\nLast commit:"
git log --oneline -1
ENDSSH
