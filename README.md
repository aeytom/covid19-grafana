# Robert-Koch-Institut Corid-19 Timeline

Prototyp eines Grafana Dashboards zur Anzeige eines Dashboards über die Timeline der RKI Zahlen zur Verbreitung von Covid-19 in Deutschland. 

Komponenten:

- Grafana Dashboard
- influxdb Datenbank
- PHP Importer

Datengrundlage:

- https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/dd4580c810204019a7b8eb3e0b329dd6_0
- https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/917fc37a709542548cc3be077a786c17_0

Der fertige Prototyp:

- https://corona.tay-tec.de/d/ryhJ18rWk/corona-rki?orgId=1&refresh=2h
- https://corona.tay-tec.de/d/wqrGytpMk/corona-rki-cases?orgId=1&refresh=2h

(Da privat gehosted, wird der Zugriff bei zu viel Traffic gesperrt)


## Bau

```
docker build -f .devcontainer/Dockerfile -t localhost:32000/coronarki .
docker push localhost:32000/coronarki:latest
helm3 upgrade --install -n corona coronarki coronarki/
```

## Deployment via helm in Kubernetes

- angepasste values.yaml anlegen
- Anpassen der grafana Sektion (ingress Host, configmap Namen, ...)
- `helm upgrade --install -n NAMESPACE RELEASENAME coronarki`

