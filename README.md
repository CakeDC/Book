# CakeDC/Book plugin for CakePHP

Are you lost in all these browser tabs? Are you a console animal? This project is for you!

book.cakephp.org search served directly at your console.

## Installation

Into an existing project

```
composer require cakedc/cakephp-book
bin/cake plugin load CakeDC/Book
```

Globally in your dev environment

```
# pick a location for the new repo, for example /var/virtual
composer create-project cakephp/app book
cd book
bin/cake plugin load CakeDC/Book
```

Then add an alias to your shell environment, for example this one if valid for zsh:

```
# edit your ~/.zshrc file, add at the end
function b () {
    php8.0 /var/virtual/book/bin/cake.php book "$*"
}
```

Note you'll need to tweak your php version and the target directory where you installed the book.

Now open a new console or refresh your environment and you'll get a very convenient alias for 

```
$ b model aftersave
```

## Usage

```
bin/cake book 'model aftersave'
```

And you'll get 

```
bin/cake book 'model aftersave'

Page 1
[1] afterSave: Cake\ORM\Table::afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) The Model.afterSave event is fired after an entity is saved.
   https://book.cakephp.org/4/en/orm/table-objects.html#aftersave
[2] Using Groups: Sometimes you will want to mark multiple cache entries to belong to certain group or namespace. This is a common requirement for mass-invalidating keys whenever some information ch
   https://book.cakephp.org/4/en/core-libraries/caching.html#using-groups
[q] Quit
Please select the topic that you want to read [1-2]
> 
```
