---
description: Configure the settings of your project
---

# Configuration file

The project has a JSON configuration file in the root directory, named config.json.

This file can store any value used by the framework, by other packages or also by you. So feel free to place anything you need here.

{% hint style="warning" %}
This JSON file is protected against external access by the .htaccess file included in the project. So please, make sure you use it to avoid a security hole.
{% endhint %}

### Read/Write from configuration

In any place of the code you can get a configuration value by using the **Config** class:

```
Config::get('value');
```

If the value is a JSON **object**, you will get an **array**. But you can get an inner value using dot notation:

```
Config::get('database.username');
```

You can change the value of a configuration value in runtime using:

```
Config::set('some.value', $value);
```

However this method will only change the value of that configuration of this PHP execution. If instead you want to overwrite the JSON file and save the change, then use:

```
Config::save('some.value', $value);
```

### Environments

One of the values in the configuration file is the **environment**. This allows you to have other configuration files in the root folder, named like: **config.\<env>.json**. For example:

* config.local.json
* config.dev.json
* config.prod.json

These JSON files **must NOT** repeat all the configurations, only the values that change. Then, the values from the selected environment will overwrite the values from the default configuration file.
