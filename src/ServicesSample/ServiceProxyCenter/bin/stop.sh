#!/bin/bash
curl -s "http://$1:$2/SteadyAsHill/borker/shutdownThisNode" | jq ".message"

