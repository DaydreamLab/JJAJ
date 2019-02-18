# JJAJ
## mc command
    php artisan jjaj:mc {name} {--admin} {--front} {--component=}
### Example
    php artisan jjaj:mc Category --admin --front --component=Cms
### params:
+   name (required)

    Model name. Plase don't use plural form(ex: User, ~~Users~~). 
+   --admin (optional)

    This param will generate admin model.
+   --front (optional)

    This param will generate front model.
+   --component= (optional)

    If you want to write you own package, this will help you to generate corresponding files with correct namespace.
    File path will be in __root/packages/component__.

