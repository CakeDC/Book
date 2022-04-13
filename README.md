# CakeDC/Book plugin for CakePHP

Are you lost in all these browser tabs? Are you a console animal? This project is for you!

book.cakephp.org search served directly at your console.

## Installation

```
composer require cakedc/cakephp-book
bin/cake plugin load CakeDC/Book
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
