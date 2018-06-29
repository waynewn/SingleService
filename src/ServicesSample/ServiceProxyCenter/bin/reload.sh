#!/bin/bash
curl -s "http://$1:$2/SteadyAsHill/broker/reloadConfigDefault"| jq ".message"

