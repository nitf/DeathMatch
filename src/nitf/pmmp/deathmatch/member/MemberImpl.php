<?php

namespace nitf\pmmp\deathmatch\member;

use pocketmine\Player;
use pocketmine\level\Position;
use nitf\pmmp\deathmatch\Messenger;
use nitf\pmmp\deathmatch\config\Setting;
use nitf\pmmp\deathmatch\team\TeamManager;
use nitf\pmmp\deathmatch\game\GameManager;
use nitf\pmmp\deathmatch\event\MemberRespawnEvent;
use nitf\pmmp\deathmatch\event\MemberEntryEvent;

class MemberImpl implements Member{
    
    /** @var Player $player */
    private $player;

    /** @var Game $game */
    private $game;

    private $team_name;

    private $kill = 0;
    private $death = 0;

    public function __construct(Player $player){
        $this->player = $player;
    }

    public function entry(): void{
        $this->game = GameManager::matching();
        if (empty($this->game)){
            $this->player->sendMessage(Messenger::get('nothing-game'));
            MemberRepository::unregister($this->player);
            return;
        }
        $arena = $this->game->getName();
        $team = TeamManager::get($arena);
        $this->team_name = $team->matching();
        if (empty($this->team_name)){
            $this->player->sendMessage(Messenger::get('nothing-team'));
            MemberRepository::unregister($this->player);
            return;
        }
        (new MemberEntryEvent($this))->call();
        $needles = ["%GAME%" => $arena, "%TEAM%" => $this->team_name];
        $message = Messenger::get('entry');
        foreach ($needles as $subject => $replace){
            $rewrited_message = str_replace($subject, $replace, $message);
        }
        $this->player->sendMessage($rewrited_message);
        $team->addMember($this->team_name, $this);
    }

    public function addKill(): void{
        $this->kill++;
    }

    public function addDeath(): void{
        $this->death++;
    }

    public function getKill(): int{
        return $this->kill;
    }

    public function getDeath(): int{
        return $this->death;
    }

    public function spawnToLobby(): void{
        $spawn_pos = Setting::getConfig()->get('lobby');
        $this->player->teleport(new Position($spawn_pos['x'], $spawn_pos['y'], $spawn_pos['z'], $spawn_pos['level']);
    }

    public function respawn(): void{
        (new MemberRespawnEvent($this))->call();
        $team_settings = $this->getTeamSetting();
        if (Setting::getConfig()->get('auto-setting')){
            $inventory = $this->player->getInventory();
            $inventory->clearAll();
            foreach ($team_settings['weapon'] as $weapon){
                $item_id = explode(':', $weapon);
                $id = (int) $item_id[0];
                $meta = (int) $item_id[1];
                $inventory->addItem(Item::get($id, $meta, 1));
            }
            $armors = $team_settings['armor'];
            $armor_inventory = $player->getArmorInventory();
            $armor_inventory->setHelmet(Item::get($armor['helmet'], 0, 1));
            $armor_inventory->setChestplate(Item::get($armor['chestplate'], 0, 1));
            $armor_inventory->setLeggings(Item::get($armor['leggings'], 0, 1));
            $armor_inventory->setBoots(Item::get($armor['boots'], 0, 1));
        }
        $spawn_pos = $team_settings['spawn-pos'];
        $this->player->teleport(new Position($spawn_pos['x'], $spawn_pos['y'], $spawn_pos['z'], $this->getArena()->getName()));
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getName(): string{
        return $this->player->getName();
    }

    public function getArena(): Arena{
        return $this->game->getArena();
    }

    public function getGame(): Game{
        return $this->game;
    }

    public function getTeamSetting(): array{
        return $this->getArena()->getConfig()->get('team')[$this->team_name];
    }

    public function getTeam(): string{
        return $this->team_name;
    }
}
