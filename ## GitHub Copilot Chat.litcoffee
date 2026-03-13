## GitHub Copilot Chat

- Extension: 0.39.0 (prod)
- VS Code: 1.111.0 (ce099c1ed25d9eb3076c11e4a280f3eb52b4fbeb)
- OS: win32 10.0.22000 x64
- GitHub Account: AndyPearman89

## Network

User Settings:
```json
  "http.systemCertificatesNode": true,
  "github.copilot.advanced.debug.useElectronFetcher": true,
  "github.copilot.advanced.debug.useNodeFetcher": false,
  "github.copilot.advanced.debug.useNodeFetchFetcher": true
```

Connecting to https://api.github.com:
- DNS ipv4 Lookup: Error (12 ms): getaddrinfo ENOTFOUND api.github.com
- DNS ipv6 Lookup: Error (3 ms): getaddrinfo ENOTFOUND api.github.com
- Proxy URL: None (17 ms)
- Electron fetch (configured): Error (6 ms): Error: net::ERR_INTERNET_DISCONNECTED
	at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
	at SimpleURLLoaderWrapper.emit (node:events:519:28)
  [object Object]
  {"is_request_error":true,"network_process_crashed":false}
- Node.js https: Error (8 ms): Error: getaddrinfo ENOTFOUND api.github.com
	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
- Node.js fetch: Error (13 ms): TypeError: fetch failed
	at node:internal/deps/undici/undici:14902:13
	at process.processTicksAndRejections (node:internal/process/task_queues:105:5)
	at async n._fetch (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5080:5229)
	at async n.fetch (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5080:4541)
	at async u (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5112:186)
	at async Zm._executeContributedCommand (file:///c:/Users/pearl/AppData/Local/Programs/Microsoft%20VS%20Code/ce099c1ed2/resources/app/out/vs/workbench/api/node/extensionHostProcess.js:494:48675)
  Error: getaddrinfo ENOTFOUND api.github.com
  	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)

Connecting to https://api.githubcopilot.com/_ping:
- DNS ipv4 Lookup: Error (0 ms): getaddrinfo ENOTFOUND api.githubcopilot.com
- DNS ipv6 Lookup: Error (0 ms): getaddrinfo ENOTFOUND api.githubcopilot.com
- Proxy URL: None (5 ms)
- Electron fetch (configured): Error (2 ms): Error: net::ERR_INTERNET_DISCONNECTED
	at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
	at SimpleURLLoaderWrapper.emit (node:events:519:28)
  [object Object]
  {"is_request_error":true,"network_process_crashed":false}
- Node.js https: Error (9 ms): Error: getaddrinfo ENOTFOUND api.githubcopilot.com
	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
- Node.js fetch: Error (14 ms): TypeError: fetch failed
	at node:internal/deps/undici/undici:14902:13
	at process.processTicksAndRejections (node:internal/process/task_queues:105:5)
	at async n._fetch (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5080:5229)
	at async n.fetch (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5080:4541)
	at async u (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5112:186)
	at async Zm._executeContributedCommand (file:///c:/Users/pearl/AppData/Local/Programs/Microsoft%20VS%20Code/ce099c1ed2/resources/app/out/vs/workbench/api/node/extensionHostProcess.js:494:48675)
  Error: getaddrinfo ENOTFOUND api.githubcopilot.com
  	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)

Connecting to https://copilot-proxy.githubusercontent.com/_ping:
- DNS ipv4 Lookup: Error (1 ms): getaddrinfo ENOTFOUND copilot-proxy.githubusercontent.com
- DNS ipv6 Lookup: Error (1 ms): getaddrinfo ENOTFOUND copilot-proxy.githubusercontent.com
- Proxy URL: None (12 ms)
- Electron fetch (configured): Error (2 ms): Error: net::ERR_INTERNET_DISCONNECTED
	at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
	at SimpleURLLoaderWrapper.emit (node:events:519:28)
  [object Object]
  {"is_request_error":true,"network_process_crashed":false}
- Node.js https: Error (9 ms): Error: getaddrinfo ENOTFOUND copilot-proxy.githubusercontent.com
	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
- Node.js fetch: Error (13 ms): TypeError: fetch failed
	at node:internal/deps/undici/undici:14902:13
	at process.processTicksAndRejections (node:internal/process/task_queues:105:5)
	at async n._fetch (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5080:5229)
	at async n.fetch (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5080:4541)
	at async u (c:\Users\pearl\.vscode\extensions\github.copilot-chat-0.39.0\dist\extension.js:5112:186)
	at async Zm._executeContributedCommand (file:///c:/Users/pearl/AppData/Local/Programs/Microsoft%20VS%20Code/ce099c1ed2/resources/app/out/vs/workbench/api/node/extensionHostProcess.js:494:48675)
  Error: getaddrinfo ENOTFOUND copilot-proxy.githubusercontent.com
  	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)

Connecting to https://mobile.events.data.microsoft.com: Error (2 ms): Error: net::ERR_INTERNET_DISCONNECTED
	at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
	at SimpleURLLoaderWrapper.emit (node:events:519:28)
  [object Object]
  {"is_request_error":true,"network_process_crashed":false}
Connecting to https://dc.services.visualstudio.com: Error (2 ms): Error: net::ERR_INTERNET_DISCONNECTED
	at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
	at SimpleURLLoaderWrapper.emit (node:events:519:28)
  [object Object]
  {"is_request_error":true,"network_process_crashed":false}
Connecting to https://copilot-telemetry.githubusercontent.com/_ping: Error (9 ms): Error: getaddrinfo ENOTFOUND copilot-telemetry.githubusercontent.com
	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
Connecting to https://copilot-telemetry.githubusercontent.com/_ping: Error (16 ms): Error: getaddrinfo ENOTFOUND copilot-telemetry.githubusercontent.com
	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
Connecting to https://default.exp-tas.com: Error (13 ms): Error: getaddrinfo ENOTFOUND default.exp-tas.com
	at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)

Number of system certificates: 103

## Documentation

In corporate networks: [Troubleshooting firewall settings for GitHub Copilot](https://docs.github.com/en/copilot/troubleshooting-github-copilot/troubleshooting-firewall-settings-for-github-copilot).