<virtual-mta $p_vmta>
    smtp-source-host $p_ip $p_domain
    $p_dkim
    <domain *>
        max-msg-rate 350/h
    </domain>
</virtual-mta>

<domain $p_domain>
    route [127.0.0.1]:25
</domain>