[![Tests](https://github.com/vvlab-dev/ConcreteCMS-keycloak/actions/workflows/tests.yml/badge.svg)](https://github.com/vvlab-dev/ConcreteCMS-keycloak/actions/workflows/tests.yml)

# Keycloak Authentication for Concrete CMS

This package provides a concrete5/ConcreteCMS authentication type that interacts with a [Keycloak](https://www.keycloak.org/) server to authorize users.

It *may* also work with other OpenID providers


## Keycloak Setup

First of all, you need a Keycloak server up and running.

In the Keycloak Admin Console you have to create a new realm (It is not recommended to use the existing *master* realm).

You then need to create a new Client with "Client authentication" turned on: you'll need its Client ID and Client secret.


## User Attributes

This package can update the attributes of a Concrete user accordingly to the attributes defined in the Keycloak server.

If you want to add a custom attribute, in the Keycloak admin console you have to:

1. Edit the `profile` client scope, adding a mapper using the `User Attribute` configuration.
2. Edit your users, adding an attribute with the name specified above.
3. Configure the mappings in the Concrete dashboard page accordingly.


## User Groups

This package can update the groups a Concrete user belongs to, accordingly to the groups defined in the Keycloak server.

In order to do that, in the Keycloak admin console you have to edit the `profile` client scope, adding a mapper by using the `Group Membership` configuration and instructing Keycloak to add it to the ID Token.

Next, you have to configure the mappings in the Concrete dashboard page accordingly.


