/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

window.akeeba                            = window.akeeba || {};
window.akeeba.ReleaseSystem              = window.akeeba.ReleaseSystem || {};
window.akeeba.ReleaseSystem.ControlPanel = {};

window.akeeba.ReleaseSystem.ControlPanel.showCharts = function ()
{
    var data       = Joomla.getOptions("akeeba.ReleaseSystem.ControlPanel.downloadsReport", {});
    var lineLabels = [];
    var dlPoints   = [];

    for (var i = 0; i < data.length; i++)
    {
        var item = data[i];
        lineLabels.push(item.date);
        dlPoints.push(
            {
                "x": item.date,
                "y": parseInt(item.count * 100) / 100
            }
        );
    }

    new Chart(document.getElementById("mdrChart"), {
        type:    "line",
        data:    {
            labels:   lineLabels,
            datasets: [
                {
                    data:        dlPoints,
                    fill:        false,
                    borderColor: "rgb(75, 192, 192)",
                    lineTension: 0.1
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    type:  "time",
                    time:  {
                        round:         "day",
                        tooltipFormat: "ll",
                        unit:          "day",
                        minUnit:       "day",
                    },
                    ticks: {
                        color: "#F0AD4E"
                    }
                },
                y: {
                    ticks: {
                        beginAtZero: true
                    }
                }
            }
        }
    });
};
window.addEventListener("DOMContentLoaded", function ()
{
    window.akeeba.ReleaseSystem.ControlPanel.showCharts();
});