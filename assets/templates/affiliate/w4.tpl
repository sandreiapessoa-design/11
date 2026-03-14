<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script>
    $(function(){
        $('#sub').click();
    });
</script>
<pre>Redirecting ....</pre>
<form action="$p_action/users/login" method="post" autocomplete="off">
    <div id="document" class="group">
        <input type="hidden" name="email" value="$p_username" id="login--email"/>
        <input type="hidden" name="password" value="$p_password" id="login--password"/>
        <input id="sub" type="submit" value="Log In"/>
    </div>
</form>