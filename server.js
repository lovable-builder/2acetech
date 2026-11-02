const http = require("node:http");

const port = process.env.PORT || 3000;

const server = http.createServer((req, res) => {
  res.writeHead(200, { "Content-Type": "text/plain; charset=utf-8" });
  res.end("2acetech is running\n");
});

server.listen(port, () => {
  console.log(`Listening on http://localhost:${port}`);
});
