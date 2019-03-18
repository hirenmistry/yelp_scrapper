from lxml.html import fromstring
import requests
from itertools import cycle
import traceback
import random
import json

def get_proxies():
    url = 'https://www.sslproxies.org/'
    response = requests.get(url)
    parser = fromstring(response.text)
    proxies = list()
    for i in parser.xpath('//tbody/tr')[:20]:
        if i.xpath('.//td[7][contains(text(),"yes")]'):
            proxy = ":".join([i.xpath('.//td[1]/text()')[0], i.xpath('.//td[2]/text()')[0]])
            proxies.append(proxy)
    return proxies

y = json.dumps(get_proxies())
# the result is a JSON string:
print(y)