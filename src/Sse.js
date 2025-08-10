var ProcessWire = ProcessWire || {};

(() => {
  class Sse {
    stream(url, callback) {
      return new Stream(url, callback);
    }
  }

  class Stream {
    started = false;
    progressCallback = null;
    throttle = 750;

    constructor(name, onMessage) {
      this.name = name;
      this.urlParams = { sse: name };
      this.onMessage = onMessage;
      this.rootUrl = ProcessWire.config.urls.root || "/";
    }

    async getUserToken() {
      return fetch("/sse-user-token")
        .then((response) => response.text())
        .then((key) => key);
    }

    onProgress(callback, throttle = null) {
      this.progressCallback = callback;
      if (throttle) this.throttle = throttle;
    }

    /**
     * Write data to a textarea, keeping the last `maxLines` lines.
     */
    prepend(textarea, data, maxLines = 100) {
      let currentValue = textarea.value;
      let lines = currentValue.split("\n");
      if (lines.length > maxLines) lines = lines.slice(0, maxLines);
      textarea.value = data + "\n" + lines.join("\n");
    }

    setProgress(event) {
      if (!this.progressCallback) return;
      if (!event.data.startsWith("{")) return;
      try {
        const data = JSON.parse(event.data);
        if (!data.iterator) return;
        this.progress = data.iterator;
      } catch (error) {
        console.error(error);
      }
    }

    async start(params) {
      if (this.started) return;
      this.started = true;
      this.startInterval();
      if (this.progressCallback) {
        this.progress = {
          num: 0,
          max: 0,
          percent: 0,
        };
        this.progressCallback(this.progress);
      }

      const key = await this.getUserToken();

      // merge params with url params
      const urlParams = { ...this.urlParams, ...params };
      urlParams.user = key;

      const evtSource = new EventSource(this.url(urlParams), {
        withCredentials: true,
      });
      this.evtSource = evtSource;
      evtSource.onmessage = (event) => {
        if (event.data === "SSE_STOP") this.stop();
        this.onMessage(event);
        this.setProgress(event);
      };

      // override this before calling start() for custom error handling
      evtSource.onerror = (event) => {
        // Check if this is a page unload/reload scenario
        if (document.visibilityState === "hidden") {
          return;
        }

        // Handle different error states
        switch (event.target.readyState) {
          case EventSource.CONNECTING:
            console.log("SSE reconnecting...");
            break;
          case EventSource.CLOSED:
            console.log("SSE connection closed");
            break;
          default:
            console.warn("SSE connection error");
        }
      };
    }

    startInterval() {
      this.interval = setInterval(() => {
        if (!this.progress) return clearInterval(this.interval);
        if (!this.progressCallback) return;
        this.progressCallback(this.progress);
        if (!this.started) clearInterval(this.interval);
      }, this.throttle);
    }

    stop() {
      if (!this.started) return;
      this.evtSource.close();
      this.started = false;
    }

    url(params) {
      const path = this.rootUrl;
      const queryString = new URLSearchParams(params).toString();
      return queryString ? `${path}?${queryString}` : path;
    }
  }

  ProcessWire.Sse = new Sse();
})();
