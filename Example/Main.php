<?php
/*

 _____      _____       ____ _____   ______          _____           _____
|\    \    /    /| ____|\   \\    \ |\     \    ____|\    \     ____|\    \  _____      _____
| \    \  /    / |/    /\    \\    \| \     \  /     /\    \   /     /\    \ \    \    /    /
|  \____\/    /  /    |  |    \|    \  \     |/     /  \    \ /     /  \    \ \    \  /    /
 \ |    /    /  /|    |__|    ||     \  |    |     |    |    |     |    |    | \____\/____/
  \|___/    /  / |    .--.    ||      \ |    |     |    |    |     |    |    | /    /\    \
      /    /  /  |    |  |    ||    |\ \|    |\     \  /    /|\     \  /    /|/    /  \    \
     /____/  /   |____|  |____||____||\_____/| \_____\/____/ | \_____\/____/ /____/ /\ \____\
    |`    | /    |    |  |    ||    |/ \|   ||\ |    ||    | /\ |    ||    | /    |/  \|    |
    |_____|/     |____|  |____||____|   |___|/ \|____||____|/  \|____||____|/|____|    |____|
       )/          \(      )/    \(       )/      \(    )/        \(    )/     \(        )/
       '            '      '      '       '        '    '          '    '       '        '

*/
namespace Example;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use Yanoox\DialogueUIAPI\DialogueAPI;
use Yanoox\DialogueUIAPI\element\DialogueButton;

final class Main extends PluginBase
{
    protected function onEnable(): void
    {
        $dialogue = DialogueAPI::create("DialogueTest", "Title", "Here's the dialogue",
            [
                DialogueButton::create("I agree")
                    ->setHandler(function (Player $player, string $buttonName): void {
                        $player->sendMessage("You've selected $buttonName.");
                    }),
                DialogueButton::create("Yanoox the best")
                    ->setHandler(function (Player $player, string $buttonName): void {
                        $player->sendMessage("Go starify his repository.");
                    })]);
        Server::getInstance()->getPluginManager()->registerEvent(PlayerEntityInteractEvent::class, function (PlayerEntityInteractEvent $event) use ($dialogue) {
            $player = $event->getPlayer();
            $entity = $event->getEntity();
            $dialogue->displayTo([$player], $entity);
        }, EventPriority::LOWEST,
            $this);

        Server::getInstance()->getPluginManager()->registerEvent(PlayerItemUseEvent::class, function (PlayerItemUseEvent $event) use ($dialogue) {
            $player = $event->getPlayer();
            if ($event->getItem()->getId() == VanillaItems::STONE_SWORD()->getId()) {
                $dialogue->displayTo([$player]);
            }
        }, EventPriority::LOWEST,
            $this);
    }
}