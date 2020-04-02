# Robert-Koch-Institut Corid-19 Timeline

Prototyp eines Grafana Dashboards zur Anzeige eines Dashboards über die Timeline der RKI Zahlen zur Verbreitung von Covid-19 in Deutschland. 

Derzeit gibt es keine Konfiguration, sondern nur die fest eingestellten Parameter im Import-Script. Der Betrieb erfolgt derzeit in einer Microk8s Umgebung mit der schon vorhandenen Influxdb und Grafana Instanz.

Da der Inital-Import bei mir schon erfolgt ist, werden nur die Daten der letzten fünf Tage permanent aktualisiert.

Datengrundlage: https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/dd4580c810204019a7b8eb3e0b329dd6_0

Der fertige Prototyp: https://graf.tay-tec.de/d/ryhJ18rWk/corona-rki?orgId=3 (Da privat gehosted, wird der Zugriff bei zu viel Traffic gesperrt)



## Bau

```
docker build -f .devcontainer/Dockerfile -t localhost:32000/coronarki
docker push localhost:32000/coronarki:latest
helm3 upgrade --install --recreate-pods -n corona coronarki coronarki/
```

## Grafana Dashboard

* Anlegen einer influxdb-Datasource
* Import von [Corona RKI-1585720948749.json](./Corona RKI-1585720948749.json)
