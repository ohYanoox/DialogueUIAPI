# DialogueUIAPI
<p align="center">
  <img src="./presentation.jpg" alt="presentation" height="180" /> <br>
</p>
**DialogueUIAPI is an API in PocketMine-MP 4.0.0 that allows you to easily create and manage a dialogue interface for NPCs.**

## SETUP
**Only put the api in the src of your plugin and use it :)**

You will find an example in Example\Main.php

## Run this code when your plugin is activated. The function will register an essential listener
```php
DialogueAPI::register($this);
```

## Crate an instance of DialogueAPI
```php
$dialogue = DialogueAPI::create("DialogueTest", "Title", 0, "Here's the dialogue", 
    [
        DialogueButton::create("I agree")
            ->setHandler(function (Player $player, string $buttonName): void {
                $player->sendMessage("You've selected $buttonName.");
            }), 
        DialogueButton::create("Yanoox the best")
            ->setHandler(function (Player $player, string $buttonName): void {
                $player->sendMessage("Go starify his repository");
            })]);
```

## Send the interface to the players
Note that for this version of DialogueUIAPI, entity is required

```php
$dialogue->displayTo([$player], $entity);
```

## There you go! You can now create with certainty NPC interfaces for your server. Have a nice day ;)
