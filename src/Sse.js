var ProcessWire = ProcessWire || {};

(() => {
  class Sse {
    stream(url, callback) {
      return new Stream(url, callback);
    }
  }

  class Stream {
    started = false;

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

    async start(params) {
      if (this.started) return;
      this.started = true;

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
