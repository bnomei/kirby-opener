<style>
.opener-license-box {
  display: flex;
  align-items: center;
}
.opener-license-box a {
  font-weight: 600;
  white-space: nowrap;
}
.opener-license-box a:after {
  content: "›";
  padding-left: .25em;
}
</style>
<div class="opener-box opener-license-box">
  <div style="padding: 0 1em 0 0;">
    <span class="fa fa-warning fa-2x"></span>
  </div>
  <div class="text">
    <?php echo $text ?>
  </div>
</div>
