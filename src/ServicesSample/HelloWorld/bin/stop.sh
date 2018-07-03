#!/bin/bash
curl -s "http://127.0.0.1:$2/SteadyAsHill/broker/shutdownThisNode"| jq ".message"

