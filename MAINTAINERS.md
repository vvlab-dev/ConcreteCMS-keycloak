## Publishing a new release

1. Update the value of the `$pkgVersion` property of the root `controller.php` file (eg `protected $pkgVersion = '1.2.3';`)
2. Commit that change (for example with the commit message `Version 1.2.3`)
3. Create a git tag with the same name as the value of `$pkgVersion` (eg `1.2.3`)
4. Push the tag to the GitHub repository
5. After a bit, you'll see the new version [available on Packagist](https://packagist.org/packages/vvlab/keycloak_auth)
6. Publish [a new GitHub release](https://github.com/vvlab-dev/ConcreteCMS-keycloak/releases/new), using the tag you just pushed. After a while, the [`create-release-attachment.yml`](https://github.com/vvlab-dev/ConcreteCMS-keycloak/actions/workflows/create-release-attachment.yml) GitHub Action should append a ZIP file to the release (eg `keycloak_auth-v1.2.3.zip`)
7. Download that .zip file and upload it to the ConcreteCMS marketplace
