<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script>
    $(function(){
        $('#loginButton').click();
    });
</script>
<pre>Redirecting ....</pre>
<form id="loginForm" method="post" action="$p_action" style="visibility: hidden" autocomplete="off">
    <input type="hidden" name="_method" value="POST" />
    <input type="hidden" name="data[_Token][key]" value="0b2b34aa1eb67687f6456168a8da2743a9baf5b3" id="Token2126142022" />
    <input name="data[User][email]" type="hidden" class="input_med" value="$p_username" id="UserEmail" />
    <input type="hidden" name="data[User][password]" class="input_med" label="" div="" value="$p_password" id="UserPassword" />
    <input type="hidden" name="data[_Token][fields]" value="56b682232e568ff7c2e5968393c245234b610de2%3An%3A0%3A%7B%7D" id="TokenFields73393745" />
    <input type="submit" class="button" id="loginButton" value="Login" />
</form>