MineReset
=========
MineReset is a fancy tool which allows you to create resettable mines the right way. It has been entirely rewritten in version 3.0.


## Commands

### /mine create \<name>
**minereset.command.create**

This command must be run in game. It will start the creation wizard to create a mine with the specified name. This will require you to tap two points to be the corner blocks of the mine. Upon tapping the second block, the mine will be created and saved.


### /mine set \<name> \<data>
**minereset.command.set**

In order for MineReset to reset a mine, it needs to know what blocks to put in it and what how often each block should occur. You must specify this in the form `<ID> <PERCENT>`. So if I wanted mine A to have 50% stone and 50% air, I would run `/mine set A 1 50 0 50`. MineReset can handle metadata for blocks in the form `<ID>:<METADATA> <PERCENT>`. 


### /mine reset \<name>
**minereset.command.reset**

This command wil trigger a manual reset if there is not already one ongoing. Mines are reset in the following way
1. Chunks in the mine are collected and turned into raw data.
2. These chunks are loaded and modfied in a seperate task (PocketMine can keep running at the same time).
3. The chunks are transferred back to PocketMine and set in bulk.

This means that any changes you make to the chunks while the mine is resetting will have no effect and MineReset will attempt to block you.

### /mine reset-all
**minereset.command.resetall**

This command will dispatch a reset for each mine.

### /mine destroy <name> \[code]
**minereset.command.destroy**

This command will irreversible delete a mine. When running this command you will be asked to confirm your action by re-running the command with a confirmation code. If you would like to avoid confirming, you should edit the `mines.yml` file directly.

### /mine list
**minereset.command.list**

This command will list out all existing mines.

### /mine about
**minereset.command.about**

This command will display a little info blurb downloaded from the web. 
