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
namespace Yanoox\DialogueUIAPI\listener;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\NpcRequestPacket;
use pocketmine\utils\AssumptionFailedError;
use Yanoox\DialogueUIAPI\data\DialoguePoolData;
use pocketmine\event\player\PlayerQuitEvent;

final class PacketHandler implements Listener
{
    public function onReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        if ($packet instanceof NpcRequestPacket) {
            $request = $packet->requestType;
            $player = $event->getOrigin()->getPlayer();
            if ($player == null) throw new AssumptionFailedError("Player is not connected");
            $dialogue = DialoguePoolData::$queue[$player->getUniqueId()->toString()][$packet->sceneName] ?? null;
            if ($dialogue === null) return;
            switch ($request) {
                case NpcRequestPacket::REQUEST_EXECUTE_ACTION:
                    $dialogue->onClose([$player]);
                    $dialogue->onClick($player, $packet->actionIndex);
                    break;
                case NpcRequestPacket::REQUEST_EXECUTE_CLOSING_COMMANDS:
                    $dialogue->onClose([$player]);
                    break;
            }
            //TODO: creative mode packets
        }
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (!isset(DialoguePoolData::$queue[$player->getUniqueId()->toString()])) return;
        unset(DialoguePoolData::$queue[$player->getUniqueId()->toString()]);
    }
}
