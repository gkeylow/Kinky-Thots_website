#!/bin/bash
# Applied on every container start
postconf -e "smtpd_client_message_rate_limit=30"
postconf -e "smtpd_client_recipient_rate_limit=50"
postconf -e "smtpd_client_connection_rate_limit=100"
postconf -e "anvil_rate_time_unit=60s"
