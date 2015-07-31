#!/bin/bash

jekyll build
rsync -ravz _site/ deptweb:/web/groups/npc15/
