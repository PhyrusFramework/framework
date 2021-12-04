---
description: You can choose how to route your pages
---

# Routing strategies

Routing refers to decide what you see when you access a specific route of your website (https://mysite.com/contact).

Phyrus gives you three different ways or strategies to route your page:

* Automatic routing
* Manual routing
* Route finder

### Automatic routing

If you place your pages under the src/pages directory, then Phyrus will automatically route the page for you by matching the name of the folder with the name of the URI.

For instance, https://mysite.com/contact would load /src/pages/contact.

{% hint style="info" %}
Inside the page folder, you need to create a Controller, read about them later.
{% endhint %}

### Manual routing

This is the classic routing that you can find in many other frameworks, you need to manually write the route and the corresponding path by code.

You can do it, for example, placing a file under the /code directory, for instance, /code/routes.php.

In this case you should place your pages somewhere different to avoid automatic routing, so create a different folder under /src, for example **/src/routes** or **/src/controllers**.

```
Router::add('/contact', Path::src() . '/routes/contact-page');
```

From this point, the page loads exactly like using automatic routing.

### Route finder

The route finder consist in defining an event (a method) that will run a the moment the page is not found, and then you will have to give it the path to the page. So, instead of listing all the routes, you "**calculate**" which page to load at the moment:

```
Router::addFinder(function($route) {
    if ($route == 'contact') {
        return Path::src() . '/routes/contact-page';
    }
});
```
