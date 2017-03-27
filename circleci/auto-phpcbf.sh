phpcbf --standard=phpcs.ruleset.xml src/
if [ -n "$(git status --porcelain)" ]; then
	git config user.email "christian.m.chung@gmail.com"
    git config user.name "Christian Chung via Circle CI"
    git add .
   git commit -m "chore(phpcbf): PHPCBF Autorun via Circle CI"
    git push origin $CIRCLE_BRANCH
fi
exit 0;
