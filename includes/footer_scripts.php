<script>
    // Console Clock for verification
    console.log("%c COLLEGE AMERICANO - SYSTEM TIME VERIFICATION", "color: #0ea5e9; font-weight: bold; font-size: 14px;");
    console.log("Browser Time: " + new Date().toLocaleString());
    console.log("Server Time (America/Guatemala): " + "<?= date('Y-m-d H:i:s') ?>");
    
    // Keep-alive script for Render
    setInterval(() => {
        fetch(window.location.href)
            .then(() => console.log("%c APP KEEP-ALIVE: Ping successful", "color: #10b981; font-weight: bold;"))
            .catch(err => console.error("APP KEEP-ALIVE: Ping failed", err));
    }, 5 * 60 * 1000); // Every 5 minutes
</script>
