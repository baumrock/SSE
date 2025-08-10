<div>
  <textarea name='create-status' rows=5 class='uk-textarea uk-margin-small-bottom'></textarea>
  <input type="number" id="count" value="500" class='uk-input' style='width: 100px;'>
  <button id="create-pages" class='uk-button uk-button-primary'>Start</button>
  <button id="stop-create-pages" class='uk-button uk-button-secondary'>Stop</button>
</div>
<script>
  (() => {
    // create stream
    const stream = ProcessWire.Sse.stream(
      'ssedemo-create-pages',
      (event) => {
        const textarea = document.querySelector('textarea[name="create-status"]');
        stream.prepend(textarea, event.data, 100);
      }
    );
    // click on start button
    document.querySelector('#create-pages').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.start({
          count: document.querySelector('#count').value,
        });
      });
    // click on stop button
    document.querySelector('#stop-create-pages').addEventListener(
      'click',
      (e) => {
        e.preventDefault();
        stream.stop();
      });
  })()
</script>