<div>
  <textarea name='empty-trash-status' rows=5 class='uk-textarea uk-margin-remove'></textarea>
  <progress id="empty-trash-progress" class="uk-progress uk-margin" value="0" max="100"></progress>
  <button id="empty-trash" class='uk-button uk-button-primary'>Start</button>
  <button id="stop-empty-trash" class='uk-button uk-button-secondary'>Stop</button>
</div>
<script>
  (() => {
    // create stream
    const stream = ProcessWire.Sse.stream(
      'ssedemo-empty-trash',
      (event) => {
        const textarea = document.querySelector('textarea[name="empty-trash-status"]');
        try {
          let json = JSON.parse(event.data);
          stream.prepend(textarea, json.message, 100);
        } catch (error) {
          stream.prepend(textarea, event.data);
        }
      }
    );

    // update progress bar
    const progressBar = document.querySelector('#empty-trash-progress');
    stream.onProgress((progress) => {
      progressBar.value = progress.percent;
    });

    // click on start button
    document.querySelector('#empty-trash').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.start();
      });

    // click on stop button
    document.querySelector('#stop-empty-trash').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.stop();
      });
  })()
</script>