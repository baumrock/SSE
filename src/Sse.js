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
      this.url = "/?sse=" + name;
      this.onMessage = onMessage;
    }

    start() {
      if (this.started) return;
      this.started = true;
      const evtSource = new EventSource(this.url, { withCredentials: true });
      evtSource.onmessage = this.onMessage.bind(this);
      this.evtSource = evtSource;
    }

    stop() {
      if (!this.started) return;
      this.evtSource.close();
      this.started = false;
    }
  }

  ProcessWire.Sse = new Sse();
})();
