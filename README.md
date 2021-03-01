Command line tools to manage locales of a Jelix application.

* convertion from properties format to PO or POT format
* convertion from PO format to properties format

# installation

You can install it from Composer. In your project:

```
composer require "jelix/locales-tools"
```


# configuration

In your projects that contains modules for Jelix, with some locales, you should
put a `.jelixlocales.ini` file.

Here is its content:

```ini

; the path of a jelix application or '.4 if you have only modules into your project 
applicationPath=.

; a project id : can be a name with the version of your project
projectId=jelix 1.6.33

; header to add into each generated properties files
propertiesFileHeader="Please don't modify this file."

; default value for all modules. You can specify specifics values in sections
; of modules
[default]
; main locale used to translate from
mainLocale=en_US

; where translations should be stored. See below.
translationLocation=@module

; a section for each modules, having this name : "module:<name of the module>".
[module:jelix]
; path to the module, relative to the applicationPath path
path=jelix/core-modules/jelix/

; list of properties files to not translate.
excludePropertiesFiles=format.UTF-8.properties
;translationLocation=@module
;mainLocale=en_US

```

`translationLocation` should be a path, relative to the path of `.jelixlocales.ini`,
or an absolute path (not recommended). It can contain keywords `:locale` and 
`:module`, they will be replaced respectively by the locale and the module name.
If you want a path relative to the application path, prepend the path with `@app:`.

Example: 
```ini
translationLocation=somewhere/:locale/:module/
; or
translationLocation=@app:var/locales/:locale/:module/
```

Most of time, you may want to store translations in locations recognized by Jelix.
You can use some specific keywords that will be replace by the real path:

* `@module`, equivalent to `<module path>/locales/:locale/`
* `@app-overloads`, equivalent to `<application path>/app/overloads/:module/locales/:locale/`
* `@app-locales`, equivalent to `<application path>/locales/:locale/:module/`
* `@var-overloads`, equivalent to `<application path>/var/overloads/:module/locales/:locale/`
* `@var-locales`, equivalent to `<application path>/var/locales/:locale/:module/`

# commands

Run `vendor/bin/jelixlocales` to have helo and the list of possible commands.

