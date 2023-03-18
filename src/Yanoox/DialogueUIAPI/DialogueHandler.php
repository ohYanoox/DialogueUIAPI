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
namespace Yanoox\DialogueUIAPI;

use pocketmine\plugin\PluginBase;
use Yanoox\DialogueUIAPI\listener\PacketHandler;

final class DialogueHandler
{
    private static ?PluginBase $plugin = null;

    public static function register(PluginBase $plugin)
    {
        self::$plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents(new PacketHandler(), $plugin);
    }

    public static function getPlugin(): ?PluginBase
    {
        return self::$plugin;
    }

    public static function isRegistered(): bool
    {
        return self::$plugin != null;
    }
}