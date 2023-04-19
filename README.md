# Keycloak Authentication for Concrete CMS

This package provides a concrete5/ConcreteCMS authentication type that interacts with a [Keycloak](https://www.keycloak.org/) server to authorize users.


## Keycloak Setup

First of all, you need a Keycloak server up and running.

In the Keycloak Admin Console you have to create a new realm (It is not recommended to use the existing *master* realm).

You then need to create a new Client with "Client authentication" turned on: you'll need its Client ID and Client secret.
