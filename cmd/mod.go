package cmd

import (
	"log"
	"os"

	"github.com/urfave/cli/v2"
)

func Run() {
	app := &cli.App{
		Name:        "forge-previewer",
		Description: "Create preview deployments for pull request with Laravel Forge.",
		Commands: []*cli.Command{
			{
				Name:    "deploy",
				Aliases: []string{"d"},
				Usage:   "Deploy your pull request to Laravel Forge.",
				Action:  Deploy,
			},
		},
	}

	err := app.Run(os.Args)
	if err != nil {
		log.Fatal(err)
	}
}
