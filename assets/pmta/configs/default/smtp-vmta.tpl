<virtual-mta $p_vmta>
    smtp-source-ip $p_ip
    <domain *>
        use-unencrypted-plain-auth yes
        auth-username $p_username
        auth-password $p_password
        route $p_host:$p_port
        use-starttls yes
        require-starttls yes
    </domain>
</virtual-mta>