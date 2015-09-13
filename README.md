##Yii2 Junction Table Attributes 
=================
A simple extension allows to access column values of junction table in ORM way without declaring additional model for that table in many-to-many relation.
Extension overwrites \yii\db\ActiveQuery::viaTable() and  allows to pass there array of column names of junction table
which will be attached to child models as properties. 


## Requirements

- Yii 2.0
- PHP 5.4

## Installation

The preferred way to install this extension is through [Composer](http://getcomposer.org/).

Either run


```bash
$ composer require alexinator1/yii2-jta
```

or add

```json
"alexinator1/yii2-jta": "*"
```

to the `require` section of your `composer.json` file.


## Usage

Just inherit your both model classes related in many-to-many relation from alexinator1\jta\ActiveRecord class.


```php
class User extends \alexinator1\jta\ActiveRecord
{
    ....
}
```


```php
class Team extends \alexinator1\jta\ActiveRecord
{
    ....
}
```

and pass array of attribute names you want to attach to child model to viaTable method


```php
class Team extends ActiveRecord
{
    ...
    
    public function getMembers()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->viaTable('user_team', ['team_id' => 'id'], null, ['role', 'joined_at']);
    }

    ...
}
```

That's it. Now you can access these fields as usual properties

```php
    $team = Team::findOne($teamId);
    foreach($team->members as $user)
    {
        $role = $user->role;
        $joinDate = $user->joined_at;
        ...
    }
```

works with 'array' models as well:


```php
    $team = Team::find()->where($teamId)->asArray()->one();
    foreach($team->members as $user)
    {
        $role = $user['role'];
        $joinDate = $user['joined_at'];
        ...
    }
```

#### Note!
```
Attached pivot attributes are read-only and acceptable only for models 
were populated via relation. They overwrite all other none-declared model properties
(declared via getter or corresponded to table columns)
and are overwritten by declared properties.
```


## License

**yii2 junction table attributes ** is released under the MIT License. See the bundled `LICENSE.md` for details.


## Resources

- [Source Code](https://github.com/alexinator1/yii2-jta)