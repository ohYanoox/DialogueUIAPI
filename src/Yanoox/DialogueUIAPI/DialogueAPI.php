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

use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\entity\Entity;
use RuntimeException;
use Yanoox\DialogueUIAPI\data\DialoguePoolData;
use Yanoox\DialogueUIAPI\element\DialogueButton;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

final class DialogueAPI
{

    protected int $eid;

    protected bool $isFakeActor = false;

    /**
     * @param string $sceneName
     * @param string $npcName
     * @param string $dialogue
     * @param DialogueButton[] $buttons
     */
    public function __construct(protected string $sceneName, protected string $npcName, protected string $dialogue, protected array $buttons)
    {
        if (!DialogueHandler::isRegistered()) {
            throw new RuntimeException("DialogueUIAPI is not registered. Please call DialogueHandler::register() before using the API.");
        }
    }

    public static function create(string $sceneName, string $npcName, string $dialogue, array $buttons): self
    {
        return new self($sceneName, $npcName, $dialogue, $buttons);
    }

    /**
     * TODO: if entity is null
     * @param Player[] $players
     * @param Entity|null $entity
     * @return void
     */
    public function displayTo(array $players, ?Entity $entity = null): void
    {
        $this->eid = $entity?->getId() ?? Entity::nextRuntimeId();
        if ($entity !== null) {
            $propertyManager = $entity->getNetworkProperties();
            $propertyManager->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 1);
            $propertyManager->setString(EntityMetadataProperties::INTERACTIVE_TAG, $this->dialogue);
            $propertyManager->setString(EntityMetadataProperties::NPC_ACTIONS, json_encode(array_map(static fn(DialogueButton $data) => $data->dump(), $this->buttons)));
            $propertyManager->setString(EntityMetadataProperties::NPC_SKIN_INDEX, json_encode([
                "picker_offsets" => [
                    "scale" => [0, 0, 0],
                    "translate" => [0, 0, 0],
                ],
                "portrait_offsets" => [
                    "scale" => [1, 1, 1],
                    "translate" => [0, 0, 0]
                ]
            ]));
        } else {
            $this->isFakeActor = true;
            foreach ($players as $player) {
                $add = $player->getDirectionVector();
                $size = $player->getSize();
                $xz = -(1 + $size->getWidth());
                $add->x *= $xz;
                $add->z *= $xz;
                $add->y *= -(1 + $size->getHeight());
                $player->getNetworkSession()->sendDataPacket(
                    AddActorPacket::create(
                        $this->eid,
                        $this->eid,
                        EntityIds::VILLAGER_V2, //whatever
                        $player->getPosition()->addVector($add),
                        null,
                        $player->getLocation()->getPitch(),
                        $player->getLocation()->getYaw(),
                        $player->getLocation()->getYaw(),
                        $player->getLocation()->getYaw(),
                        [],
                        [
                            EntityMetadataProperties::NPC_ACTIONS => new StringMetadataProperty(json_encode(array_map(static fn(DialogueButton $data) => $data->dump(), $this->buttons))),
                            EntityMetadataProperties::HAS_NPC_COMPONENT => new ByteMetadataProperty(1),
                            EntityMetadataProperties::INTERACTIVE_TAG => new StringMetadataProperty($this->dialogue),
                        ],
                        new PropertySyncData([], []),
                        []
                    )
                );
            }
        }
        $dialoguePk = NpcDialoguePacket::create(
            $this->eid,
            NpcDialoguePacket::ACTION_OPEN,
            $this->dialogue,
            $this->sceneName,
            $this->npcName,
            json_encode(array_map(static fn(DialogueButton $data) => $data->dump(), $this->buttons))
        );
        foreach($players as $player) DialoguePoolData::$queue[$player->getUniqueId()->toString()][$this->sceneName] = $this;
        DialogueHandler::getPlugin()->getServer()->getInstance()->broadcastPackets($players, [$dialoguePk, $dialoguePk]);
    }

    /**
     * @param Player[] $players
     * @return void
     */
    public function onClose(array $players): void
    {
        if ($this->isFakeActor()) DialogueHandler::getPlugin()->getServer()->broadcastPackets($players, [RemoveActorPacket::create($this->getActorId())]);
        $mappedActions = json_encode(array_map(static fn(DialogueButton $data) => $data->dump(), $this->buttons));
        foreach($players as $player)
        {
            $player->getNetworkSession()->sendDataPacket(
                NpcDialoguePacket::create(
                    $this->eid,
                    NpcDialoguePacket::ACTION_CLOSE,
                    $this->dialogue,
                    $this->sceneName,
                    $this->npcName,
                    $mappedActions
                )
            );
        }
    }

    public function onClick(Player $player, int $id): void
    {
        if (!array_key_exists($id, $this->buttons)) return;
        $button = $this->buttons[$id];

        if ($button->getCloseOnClick()) $this->onClose([$player]);

        ($this->buttons[$id]->getHandler())($player, $button->getName());
    }

    public function isFakeActor(): bool
    {
        return $this->isFakeActor;
    }

    public function getActorId(): int
    {
        return $this->eid;
    }
}
