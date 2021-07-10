<?php
$font = Definition('frameworkFont');
echo '<link rel="preconnect" href="https://fonts.gstatic.com">';
echo '<link href="https://fonts.googleapis.com/css2?family=' . $font . '&display=swap" rel="stylesheet">';
echo "<style>*{ font-family: '$font', sans-serif;}</style>";
?>
<style>
<?php 
echo file_get_contents(__DIR__ . "/styles.css"); 
?>
</style>

<div class='error-handler-page'>
    
    <div class="container error-handler-box">

        <div class='error-page-title'><?php echo $title; ?></div>
        <div class='error-page-subtitle'><?php echo $subtitle; ?></div>

        <div class='error-detail'><?php echo $message; ?></div>

        <div class='file-box'>
            <div class='file-location'><?php echo $file; ?></div>
            <div class='file-line'>Line <?php echo $line; ?></div>
        </div>

        <div class="code-box">
            <?php echo $code; ?>
        </div>

        <div class='error-report'>
            <div class='error-backtrace report-col'>
                <div class='error-section-title'>Backtrace</div>

                <div class='error-backtrace-list'>
                <?php
                    foreach($backtrace as $trace) {
                        ?>
                        <div class='error-backtrace-item'>
                            <div class='error-backtrace-file'><?php echo Path::toRelative($trace['file']); ?></div>
                            <div class='error-backtrace-line'>Line <?php echo $trace['line']; ?></div>
                        </div>
                        <?php
                    }
                ?>
                </div>
            </div>

            <div class='error-suggestions report-col'>
                <div class='error-section-title'>Suggestions</div>

                <?php 
                if (!empty($suggestion)) {
                    echo $suggestion;
                } else {
                    echo 'There are no suggestions for this error.';
                } 
                
                echo '<br><br><a target="_blank" href="https://google.com/search?q=php+'. str_replace('"', '', $message) .'">Click here to google the error.</a>';
                ?>

            </div>
        </div>

    </div>

</div>