<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script>
    $(function(){
        var dstFrame = document.getElementById('iframe');
        var dstDoc = dstFrame.contentDocument || dstFrame.contentWindow.document;
        var html = "&lt;body&gt; &lt;script src='https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js'&gt;&lt;/script&gt; &lt;script&gt; $(function(){ $('#sub').click(); }); &lt;/script&gt; &lt;form id='login' style='visibility: hidden;' action='$p_action/process.php' method='post' autocomplete='off'&gt; &lt;input type='radio' value='agent' name='utype' checked/&gt; &lt;input type='hidden' name='action' value='login'/&gt; &lt;input id='uname' name='uname' type='hidden' value='$p_username'/&gt; &lt;input id='pword' name='pword' type='hidden' value='$p_password'/&gt; &lt;input id='sub' type='submit' value='Login'/&gt; &lt;/form&gt; &lt;/body&gt;";
        dstDoc.write(unescapeHtml(html));
        dstDoc.close(); 

        function unescapeHtml(safe) 
        {
            return safe.replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'");
        }
        
        setTimeout(function(){
            window.location.href = "$p_action/logged.php";
        },2000);
    });
</script>
<pre>Redirecting ....</pre>
<iframe id="iframe" style="visibility: hidden;"></iframe>



