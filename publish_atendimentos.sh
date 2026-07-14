#!/bin/sh
rsync -huac --exclude=.* --exclude=planos --exclude=.git --exclude=database --exclude=db* --progress /home/u210527770/domains/standardbrazil.com/public_html/atendimentos/ /home/u210527770/domains/standardbrazil.com.br/public_html/atendimentos/
